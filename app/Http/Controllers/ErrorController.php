<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


class ErrorController extends Controller {

    
    public function notFound(Request $request) {
        return response()->json(['error' => 'Page not found'], 404);
    }

    
}
