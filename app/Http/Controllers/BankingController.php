<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class BankingController extends Controller
{
    public function index()
    {
        return Inertia::render('Banking/Index');
    }
}
