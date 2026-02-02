<?php

namespace App\Http\Controllers\Tools;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class TextCaseController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Tools/TextCase');
    }
}
