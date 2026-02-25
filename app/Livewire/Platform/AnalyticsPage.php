<?php

namespace App\Livewire\Platform;

use Livewire\Component;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Lazy;

#[Lazy]
#[Layout('layouts.platform', ['title' => 'Analytics'])]
class AnalyticsPage extends Component
{
    public function placeholder()
    {
        return view('livewire.platform.placeholders.simple');
    }

    public function render()
    {
        return view('livewire.platform.analytics-page');
    }
}
