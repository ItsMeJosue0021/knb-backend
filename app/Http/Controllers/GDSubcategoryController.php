<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\GDSubcategory;
use Illuminate\Http\Request;

class GDSubcategoryController extends Controller
{
    public function index() {
        return response([
            'categories' => GDSubcategory::all()
        ], 200);
    }
}
