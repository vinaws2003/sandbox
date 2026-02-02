<?php

namespace App\Http\Controllers\Tools;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class Base64Controller extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Tools/Base64');
    }
}
