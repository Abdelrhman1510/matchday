<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Seat;
use App\Models\SeatingSection;
use Illuminate\Support\Facades\DB;

class SeatingAdminService
{
    /**
     * Type-to-prefix mapping for auto-generated seat labels
     */
    protected array $typePrefixes = [
        'main_screen' => 'M',
        'vip' => 'V',
        'premium' => 'P',
        'standard' => 'S',
    ];

    /**
     * List all sections for a branch with occupied/available counts
     */
    public function listSections(Branch $branch): array
    {
        $sections = $branch->seatingSections()
            ->withCount([
                'seats',
                'seats as available_seats_count' => function ($q) {
                    $q->where('is_available', true);
                },
                'seats as occupied_seats_count' => function ($q) {
                    $q->where('is_available', false);
                },
            ])
            ->get();

        // Enrich each section with active booking count
        $sections->each(function ($section) {
            $section->active_bookings_count = $this->getActiveBookingsCount($section);
        });

        return [
            'sections' => $sections,
            'summary' => [
                'total_sections' => $sections->count(),
                'total_seats' => $sections->sum('seats_count'),
                'total_available' => $sections->sum('available_seats_count'),
                'total_occupied' => $sections->sum('occupied_seats_count'),
            ],
        ];
    }

    /**
     * Create a new section with auto-generated seats
     */
    public function createSection(Branch $branch, array $data): SeatingSection
    {
        return DB::transaction(function () use ($branch, $data) {
            $section = $branch->seatingSections()->create([
                'name' => $data['name'],
                'type' => $data['type'],
                'total_seats' => $data['total_seats'],
                'extra_cost' => $data['extra_cost'] ?? 0,
                'icon' => $data['icon'] ?? null,
                'screen_size' => $data['screen_size'] ?? null,
            ]);

            // Auto-generate seats
            $this->generateSeats($section, $data['total_seats']);

            // Reload with counts
            $section->loadCount([
                'seats',
                'seats as available_seats_count' => fn($q) => $q->where('is_available', true),
                'seats as occupied_seats_count' => fn($q) => $q->where('is_available', false),
            ]);

            return $section;
        });
    }

    /**
     * Update a section
     */
    public function updateSection(SeatingSection $section, array $data): SeatingSection
    {
        return DB::transaction(function () use ($section, $data) {
            $oldTotalSeats = $section->total_seats;

            $section->update(array_filter([
                'name' => $data['name'] ?? null,
                'type' => $data['type'] ?? null,
                'total_seats' => $data['total_seats'] ?? null,
                'extra_cost' => array_key_exists('extra_cost', $data) ? $data['extra_cost'] : null,
                'icon' => array_key_exists('icon', $data) ? $data['icon'] : null,
                'screen_size' => array_key_exists('screen_size', $data) ? $data['screen_size'] : null,
            ], fn($v) => $v !== null));

            // If total_seats increased, generate additional seats
            $newTotalSeats = $data['total_seats'] ?? $oldTotalSeats;
            if ($newTotalSeats > $oldTotalSeats) {
                $currentMax = $section->seats()->count();
                $additionalSeats = $newTotalSeats - $currentMax;
                if ($additionalSeats > 0) {
                    $this->generateSeats($section, $additionalSeats, $currentMax);
                }
            }

            // If type changed, update labels for seats that follow the prefix pattern
            if (isset($data['type']) && $data['type'] !== $section->getOriginal('type')) {
                $this->relabelSeats($section, $data['type']);
            }

            $section->loadCount([
                'seats',
                'seats as available_seats_count' => fn($q) => $q->where('is_available', true),
                'seats as occupied_seats_count' => fn($q) => $q->where('is_available', false),
            ]);

            return $section->fresh();
        });
    }

    /**
     * Delete a section (only if no active bookings)
     */
    public function deleteSection(SeatingSection $section): array
    {
        $activeBookings = $this->getActiveBookingsCount($section);

        if ($activeBookings > 0) {
            return [
                'success' => false,
                'message' => "Cannot delete section: {$activeBookings} active booking(s) exist. Please wait until bookings are completed or cancel them first.",
                'active_bookings' => $activeBookings,
            ];
        }

        $sectionName = $section->name;
        $seatsCount = $section->seats()->count();

        DB::transaction(function () use ($section) {
            $section->seats()->delete();
            $section->delete();
        });

        return [
            'success' => true,
            'message' => "Section '{$sectionName}' and {$seatsCount} seat(s) deleted successfully.",
        ];
    }

    /**
     * List seats for a section with status and booking info
     */
    public function listSeats(SeatingSection $section): array
    {
        $seats = $section->seats()
            ->orderByRaw("CAST(SUBSTRING(label, 2) AS UNSIGNED)")
            ->get();

        // Eager load active bookings for each seat
        $seatIds = $seats->pluck('id');
        $activeBookingSeatIds = DB::table('booking_seats')
            ->join('bookings', 'bookings.id', '=', 'booking_seats.booking_id')
            ->whereIn('booking_seats.seat_id', $seatIds)
            ->whereIn('bookings.status', ['confirmed', 'pending'])
            ->whereNull('bookings.deleted_at')
            ->pluck('booking_seats.seat_id')
            ->unique()
            ->toArray();

        $seats->each(function ($seat) use ($activeBookingSeatIds) {
            $seat->has_active_booking = in_array($seat->id, $activeBookingSeatIds);
        });

        return [
            'seats' => $seats,
            'section' => [
                'id' => $section->id,
                'name' => $section->name,
                'type' => $section->type,
            ],
            'summary' => [
                'total' => $seats->count(),
                'available' => $seats->where('is_available', true)->count(),
                'occupied' => $seats->where('is_available', false)->count(),
                'with_active_bookings' => count($activeBookingSeatIds),
            ],
        ];
    }

    /**
     * Bulk add seats to a section
     */
    public function bulkAddSeats(SeatingSection $section, array $data): array
    {
        return DB::transaction(function () use ($section, $data) {
            $count = $data['count'];
            $currentMax = $section->seats()->count();

            // Determine prefix
            $prefix = $data['prefix'] ?? $this->typePrefixes[$section->type] ?? 'S';
            $startFrom = $data['start_from'] ?? ($currentMax + 1);

            $seats = [];
            for ($i = 0; $i < $count; $i++) {
                $seatNumber = $startFrom + $i;
                $seats[] = $section->seats()->create([
                    'label' => $prefix . $seatNumber,
                    'table_number' => $data['table_number'] ?? null,
                    'is_available' => true,
                ]);
            }

            // Update total_seats on the section
            $section->update([
                'total_seats' => $section->seats()->count(),
            ]);

            return [
                'created_count' => count($seats),
                'seats' => $seats,
                'new_total' => $section->fresh()->total_seats,
            ];
        });
    }

    /**
     * Update a single seat
     */
    public function updateSeat(Seat $seat, array $data): Seat
    {
        $updateData = [];
        if (array_key_exists('label', $data)) $updateData['label'] = $data['label'];
        if (array_key_exists('price', $data)) $updateData['price'] = $data['price'];
        if (array_key_exists('table_number', $data)) $updateData['table_number'] = $data['table_number'];
        if (array_key_exists('is_available', $data)) $updateData['is_available'] = $data['is_available'];

        if (!empty($updateData)) {
            $seat->update($updateData);
        }

        return $seat->fresh();
    }

    /**
     * Delete a seat (only if no active booking)
     */
    public function deleteSeat(Seat $seat): array
    {
        $hasActiveBooking = DB::table('booking_seats')
            ->join('bookings', 'bookings.id', '=', 'booking_seats.booking_id')
            ->where('booking_seats.seat_id', $seat->id)
            ->whereIn('bookings.status', ['confirmed', 'pending'])
            ->whereNull('bookings.deleted_at')
            ->exists();

        if ($hasActiveBooking) {
            return [
                'success' => false,
                'message' => 'Cannot delete seat: it has an active booking. Wait until the booking is completed or cancelled.',
            ];
        }

        $seatLabel = $seat->label;
        $section = $seat->section;

        $seat->delete();

        // Update total_seats on the section
        $section->update([
            'total_seats' => $section->seats()->count(),
        ]);

        return [
            'success' => true,
            'message' => "Seat '{$seatLabel}' deleted successfully.",
            'new_section_total' => $section->fresh()->total_seats,
        ];
    }

    /**
     * Bulk create sections with auto-generated seats (Branch setup Step 4)
     */
    public function bulkCreateSections(Branch $branch, array $sections): array
    {
        return DB::transaction(function () use ($branch, $sections) {
            $created = [];

            foreach ($sections as $sectionData) {
                $section = $branch->seatingSections()->create([
                    'name' => $sectionData['name'],
                    'type' => $sectionData['type'],
                    'total_seats' => $sectionData['total_seats'],
                    'extra_cost' => $sectionData['extra_cost'] ?? 0,
                    'icon' => $sectionData['icon'] ?? null,
                    'screen_size' => $sectionData['screen_size'] ?? null,
                ]);

                $this->generateSeats($section, $sectionData['total_seats']);

                $section->loadCount([
                    'seats',
                    'seats as available_seats_count' => fn($q) => $q->where('is_available', true),
                    'seats as occupied_seats_count' => fn($q) => $q->where('is_available', false),
                ]);

                $created[] = $section;
            }

            return [
                'sections' => $created,
                'summary' => [
                    'sections_created' => count($created),
                    'total_seats_created' => collect($created)->sum('seats_count'),
                ],
            ];
        });
    }

    // ========================================
    // PRIVATE HELPERS
    // ========================================

    /**
     * Auto-generate seats for a section
     */
    protected function generateSeats(SeatingSection $section, int $count, int $offset = 0): void
    {
        $parts = explode(' ', $section->name);
    $prefix = strtoupper(end($parts));

        $seats = [];
        $now = now();

        for ($i = 1; $i <= $count; $i++) {
            $seats[] = [
                'section_id' => $section->id,
                'label' => $prefix . ($offset + $i),
                'is_available' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Batch insert for performance
        foreach (array_chunk($seats, 100) as $chunk) {
            Seat::insert($chunk);
        }
    }

    /**
     * Relabel seats when section type changes
     */
    protected function relabelSeats(SeatingSection $section, string $newType): void
    {
        $newPrefix = $this->typePrefixes[$newType] ?? 'S';
        $oldPrefixes = array_values($this->typePrefixes);

        $section->seats()->each(function ($seat) use ($newPrefix, $oldPrefixes) {
            $currentLabel = $seat->label;

            // Only relabel seats that follow the standard prefix pattern
            foreach ($oldPrefixes as $prefix) {
                if (str_starts_with($currentLabel, $prefix) && is_numeric(substr($currentLabel, strlen($prefix)))) {
                    $number = substr($currentLabel, strlen($prefix));
                    $seat->update(['label' => $newPrefix . $number]);
                    break;
                }
            }
        });
    }

    /**
     * Count active bookings for a section's seats
     */
    protected function getActiveBookingsCount(SeatingSection $section): int
    {
        return DB::table('booking_seats')
            ->join('bookings', 'bookings.id', '=', 'booking_seats.booking_id')
            ->join('seats', 'seats.id', '=', 'booking_seats.seat_id')
            ->where('seats.section_id', $section->id)
            ->whereIn('bookings.status', ['confirmed', 'pending'])
            ->whereNull('bookings.deleted_at')
            ->distinct('bookings.id')
            ->count('bookings.id');
    }
}
