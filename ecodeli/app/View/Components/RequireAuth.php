<?php

namespace App\View\Components;

use Illuminate\View\Component;

class RequireAuth extends Component
{
public $role;

public function __construct($role = null)
{
$this->role = $role;
}

public function render()
{
return view('components.require-auth');
}
}
