<?php

namespace App\Livewire;

use Livewire\Component;

class ProfileManagement extends Component
{
    public function render()
    {
        return view('livewire.profile-management')->layout('layouts.app');
    }
}
