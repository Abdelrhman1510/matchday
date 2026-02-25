<?php

namespace App\Livewire\Platform;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Booking;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Lazy;

#[Lazy]
#[Layout('layouts.platform', ['title' => 'Bookings Management'])]
class BookingsPage extends Component
{
    public function placeholder()
    {
        return view('livewire.platform.placeholders.simple');
    }

    use WithPagination;

    public $search = '';
    public $status = '';

    public function render()
    {
        $bookings = Booking::with(['branch.cafe', 'user', 'match'])
            ->when($this->search, function ($query) {
                $query->whereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->latest()
            ->paginate(15);

        return view('livewire.platform.bookings-page', [
            'bookings' => $bookings
        ]);
    }
}
