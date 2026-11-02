<?php

namespace App\Livewire\Panel;

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
        $previous = $this->currentPage;
        $this->currentPage = $page;
        if ($page === 'buscador-precios') {
            $this->sidebarOpen = false;
        } elseif ($previous === 'buscador-precios') {
            $this->dispatch('exit-fullscreen');
        }
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
        return view('panel.dashboard');
    }
}