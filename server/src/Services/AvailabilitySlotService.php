<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\AvailabilitySlotRepository;
use App\Models\AvailabilitySlot;

class AvailabilitySlotService
{
    private AvailabilitySlotRepository $repo;

    public function __construct(AvailabilitySlotRepository $repo)
    {
        $this->repo = $repo;
    }

    public function createSlot(int $userId, array $data): AvailabilitySlot
    {
        $startTime = $data['start_time'] ?? '';
        $endTime = $data['end_time'] ?? '';

        if (empty($startTime) || empty($endTime)) {
            throw new \Exception('start_time and end_time are required');
        }

        // Validate times
        $start = strtotime($startTime);
        $end = strtotime($endTime);
        if ($start === false || $end === false) {
            throw new \Exception('Invalid date/time format');
        }
        if ($start >= $end) {
            throw new \Exception('start_time must be before end_time');
        }

        // Check overlap
        if ($this->repo->hasOverlap($userId, $startTime, $endTime)) {
            throw new \Exception('Time slot overlaps with existing availability');
        }

        $slot = new AvailabilitySlot([
            'user_id' => $userId,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);

        $id = $this->repo->create($slot);
        $slot->id = $id;
        return $slot;
    }

    public function getSlot(int $id): AvailabilitySlot
    {
        $slot = $this->repo->findById($id);
        if (!$slot) {
            throw new \Exception('Availability slot not found');
        }
        return $slot;
    }

    public function getUserSlots(int $userId): array
    {
        return $this->repo->findByUserId($userId);
    }

    public function updateSlot(int $id, array $data): AvailabilitySlot
    {
        $slot = $this->getSlot($id);

        if (isset($data['start_time'])) {
            $slot->start_time = $data['start_time'];
        }
        if (isset($data['end_time'])) {
            $slot->end_time = $data['end_time'];
        }

        $start = strtotime($slot->start_time);
        $end = strtotime($slot->end_time);
        if ($start === false || $end === false || $start >= $end) {
            throw new \Exception('Invalid time range');
        }

        if ($this->repo->hasOverlap($slot->user_id, $slot->start_time, $slot->end_time, $id)) {
            throw new \Exception('Time slot overlaps with existing availability');
        }

        $this->repo->update($id, $slot);
        return $slot;
    }

    public function deleteSlot(int $id): void
    {
        $this->getSlot($id);
        $this->repo->delete($id);
    }
}
