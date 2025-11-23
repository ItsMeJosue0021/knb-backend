<?php

namespace App\Http\Controllers;

use App\Models\GDCategory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class GDCategoryController extends Controller
{
    public function index() {
        return response([
            'categories' => GDCategory::with('subcategories')->get()
        ], 200);
    }
}
