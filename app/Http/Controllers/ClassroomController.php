<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ClassroomController extends Controller
{
    public function index()
    {
        // retrieve all classrooms and it's current availability
        $currentDate = Carbon::now()->format('Y-m-d');

        $sql = "SELECT c.*, 
                       (c.capacity - IFNULL(b.booked_count, 0)) as current_availability 
                FROM classrooms c
                LEFT JOIN (
                    SELECT classroom_id, COUNT(*) as booked_count 
                    FROM bookings 
                    WHERE date = :current_date 
                    GROUP BY classroom_id
                ) b ON c.id = b.classroom_id";

        $classrooms = DB::select($sql, ['current_date' => $currentDate]);

        return response()->json($classrooms);
    }

    public function book(Request $request)
    {
        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'user_name' => 'required|string',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
        ]);

        $classroomId = $validated['classroom_id'];
        $startTime = Carbon::createFromFormat('H:i', $validated['start_time']);
        $endTime = $startTime->copy()->addMinutes(60);
        $date = $validated['date'];

        // verify time conflicts
        $conflictQuery = "
            SELECT COUNT(*) as conflict_count
            FROM bookings 
            WHERE classroom_id = :classroom_id 
            AND date = :date 
            AND (
                (start_time BETWEEN :start_time AND :end_time) OR 
                (end_time BETWEEN :start_time AND :end_time)
            )
        ";

        $conflict = DB::selectOne($conflictQuery, [
            'classroom_id' => $classroomId,
            'date' => $date,
            'start_time' => $startTime->format('H:i:s'),
            'end_time' => $endTime->format('H:i:s'),
        ]);

        if ($conflict->conflict_count > 0) {
            return response()->json(['message' => 'There is a date and time conflict'], 400);
        }

        // insert reservation
        $insertQuery = "
            INSERT INTO bookings (classroom_id, user_name, date, start_time, end_time, created_at, updated_at)
            VALUES (:classroom_id, :user_name, :date, :start_time, :end_time, :created_at, :updated_at)
        ";

        DB::statement($insertQuery, [
            'classroom_id' => $classroomId,
            'user_name' => $validated['user_name'],
            'date' => $date,
            'start_time' => $startTime->format('H:i:s'),
            'end_time' => $endTime->format('H:i:s'),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return response()->json(['message' => 'Reservation created succesfully!'], 201);
    }

    public function cancel($id)
    {
        $bookingQuery = "SELECT * FROM bookings WHERE id = :id";
        $booking = DB::selectOne($bookingQuery, ['id' => $id]);

        if (!$booking) {
            return response()->json(['message' => 'Reserva no encontrada'], 404);
        }

        $hoursDiff = Carbon::now()->diffInHours(Carbon::parse($booking->date . ' ' . $booking->start_time));

        // minimum time cancelation validation
        if ($hoursDiff < 24) {
            return response()->json(['message' => "It's not possible to cancel a reservation with less than 24 hours of anticipation"], 400);
        }

        $deleteQuery = "DELETE FROM bookings WHERE id = :id";
        DB::statement($deleteQuery, ['id' => $id]);

        return response()->json(['message' => 'Reserva cancelada con éxito'], 200);
    }


}
