<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\ClassRoom;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\SlotDateAllocation;
use App\Models\SlotTimeAllocation;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ClassRoomSlotBooking extends Controller
{
    protected $classAvailable = [
        'Class A' => [
            'id' => 1,
            'day' => [1, 3],
            'time' => [
                'start' => 9,
                'end' => 18
            ],
            'hour' => 1,
            'people' => 10
        ],
        'Class B' => [
            'id' => 2,
            'day' => [1, 4, 6],
            'time' => [
                'start' => 8,
                'end' => 18
            ],
            'hour' => 2,
            'people' => 15
        ],
        'Class C' => [
            'id' => 3,
            'day' => [2, 5, 6],
            'time' => [
                'start' => 15,
                'end' => 22
            ],
            'hour' => 1,
            'people' => 7
        ],
    ];

    /**
     * This function used for get available class based slot allocation
     *
     * @return JsonResponse
     * @author Karthick
     * @date  01/21/2023
     */
    public function getAvailableClass(): JsonResponse
    {
        $availableClass = [];
        $classRoomData = ClassRoom::get();
        $getAllocation = SlotDateAllocation::with('timeAllocation')->get();


        foreach ($classRoomData as $classRoom) {
            $availableClass[$classRoom->name] = [];
            $dateSlots = $getAllocation->where('classroom_id', $classRoom->id)->all();

            foreach ($dateSlots as $dateSlot) {
                $timeSlots = $dateSlot->timeAllocation->groupBy(['combine_id']);

                foreach ($timeSlots as $combineKey => $timeSlot) {
                    $availableClass[$classRoom->name][$dateSlot->date][$combineKey] = $timeSlot->sum('people');
                }
            }
        }

        return response()->json($availableClass);
    }

    /**
     * This function used for book slot based on class, date, hours and people
     *
     * @param Request request
     *
     * @return JsonResponse
     * @author Karthick
     * @date  01/21/2023
     */
    public function bookClass(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'class' => 'required|exists:classrooms,name',
            'date' => 'required|date_format:Y-m-d',
            'start_hr' => 'required|numeric',
            'end_hr' => 'required|numeric',
            'people' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'validation' => $validator->errors(),
            ]);
        }

        $classInformation = $this->classAvailable[$request->class];

        //Classroom based day validation
        $getDay = date('N', strtotime($request->date));

        if (!in_array($getDay, $classInformation['day'])) {
            return response()->json([
                'error' => true,
                'validation' => 'The ' . $request->class . ' not available on the ' . $request->date,
            ]);
        }

        //Classroom based total time hours
        $diffHour = $request->end_hr - $request->start_hr;

        if ($diffHour != $classInformation['hour']) {
            return response()->json([
                'error' => true,
                'validation' => 'The ' . $request->class . ' should be bookable only ' . $classInformation['hour'] . ' hour(s).',
            ]);
        }

        //Classroom based time hours
        $hourRange = range($classInformation['time']['start'], $classInformation['time']['end']);

        if (!(in_array($request->start_hr, $hourRange) && in_array($request->end_hr, $hourRange))) {
            return response()->json([
                'error' => true,
                'validation' => 'Invalid ' . $request->class . ' bookable hour.',
            ]);
        }

        //Check existing people validation
        $checkPeople = 0;
        $classRoomId = $classInformation['id'];
        $dateAllocation = SlotDateAllocation::where('classroom_id', $classRoomId)->where('date', $request->date)
            ->with('timeAllocation')
            ->first();

        if ($dateAllocation && $dateAllocation->timeAllocation) {
            $checkPeople = $dateAllocation->timeAllocation->where('start_hr', $request->start_hr)
                ->where('end_hr', $request->end_hr)
                ->pluck('people')
                ->sum();
        }

        $totalPeople = $checkPeople + $request->people;

        if ($classInformation['people'] < $totalPeople) {
            return response()->json([
                'error' => true,
                'validation' => 'Already allocated ' . $checkPeople . ' people for the given hour.',
            ]);
        }

        $createDateAllocation = SlotDateAllocation::firstOrNew([
            'classroom_id' => $classRoomId,
            'date' => $request->date,
        ]);
        $createDateAllocation->save();

        SlotTimeAllocation::create([
            'slot_date_allocation_id' => $createDateAllocation->id,
            'start_hr' => $request->start_hr,
            'end_hr' => $request->end_hr,
            'people' => $request->people,
        ]);

        return response()->json([
            'error' => false,
            'validation' => 'Your slot booked successfully.',
        ]);
    }

    /**
     * This function used for cancel the already allocated class
     *
     * @param Request request
     *
     * @return JsonResponse
     * @author Karthick
     * @date  01/21/2023
     */
    public function cancelClass(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'class' => 'required|exists:classrooms,name',
            'date' => 'required|date_format:Y-m-d',
            'start_hr' => 'required|numeric',
            'end_hr' => 'required|numeric',
            'people' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'validation' => $validator->errors(),
            ]);
        }

        $classInformation = $this->classAvailable[$request->class];
        $dateAllocation = SlotDateAllocation::where('classroom_id', $classInformation['id'])->where('date', $request->date)
            ->with('timeAllocation')
            ->first();

        $timeAllocation = $dateAllocation->timeAllocation->where('start_hr', $request->start_hr)
            ->where('end_hr', $request->end_hr)
            ->where('people', $request->people)
            ->first();

        if (!$timeAllocation) {
            return response()->json([
                'error' => true,
                'validation' => 'Slot allocation not found.',
            ]);
        }

        $diffHours = Carbon::now()->diffInHours(new Carbon($timeAllocation->created_at));

        if ($diffHours > 24) {
            return response()->json([
                'error' => true,
                'validation' => 'A class cannot be canceled less than 24 hours.',
            ]);
        }

        //Cancel the class
        $timeAllocation->delete();

        return response()->json([
            'error' => false,
            'validation' => 'A class cancelled successfully.',
        ]);
    }
}
