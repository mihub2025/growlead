<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Enums\ActivityType;
use App\Enums\CallOutcome;
use App\Enums\TaskPriority;
use App\Enums\TaskType;
use App\Services\LeadFilterService;
use Illuminate\Http\Request;

class ReferenceController extends MobileController
{
    public function leadStatuses(Request $request, LeadFilterService $filters)
    {
        return $this->ok($filters->statusOptions($request->user()->organization));
    }

    public function activityTypes()
    {
        return $this->ok([
            'types' => ActivityType::options(),
            'call_outcomes' => CallOutcome::options(),
        ]);
    }

    public function taskTypes()
    {
        return $this->ok([
            'types' => TaskType::options(),
            'priorities' => TaskPriority::options(),
            'reminders' => [
                ['value' => 'none', 'label' => 'None'],
                ['value' => '15m', 'label' => '15 min before'],
                ['value' => '30m', 'label' => '30 min before'],
                ['value' => '1h', 'label' => '1 hour before'],
                ['value' => '1d', 'label' => '1 day before'],
            ],
        ]);
    }
}
