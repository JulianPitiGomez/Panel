<?php

namespace App\Livewire\Panelresto;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

class Dashboard extends Component
{
    public $sidebarOpen = true;
    public $currentPage = 'inicio';

    protected $queryString = ['currentPage'];

    public function toggleSidebar()
    {
        $this->sidebarOpen = !$this->sidebarOpen;
    }

    public function navigateTo($page)
    {
        $this->currentPage = $page;
        $this->dispatch('scroll-to-top');
    }

    #[On('panel-navigate')]
    public function panelNavigate($page)
    {
        $this->navigateTo($page);
    }

    public function logout()
    {
        return $this->redirect(route('logout.panel'), navigate: true);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('panelresto.dashboard');
    }
}