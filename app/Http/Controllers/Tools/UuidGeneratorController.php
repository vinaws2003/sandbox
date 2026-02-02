<?php

namespace App\Http\Controllers\Tools;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class UuidGeneratorController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Tools/UuidGenerator');
    }
}
