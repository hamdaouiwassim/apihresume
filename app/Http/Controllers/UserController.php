<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;


class UserController extends Controller
{

    public function myResumes(Request $request)
    {
        try {
            $resumes = Auth::user()->resumes;

            return response()->json([
                "status" => true,
                "message" => "Resumes fetched successfully",
                "data" =>
                ["resumes"=>$resumes]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status" => false,
                "message" => "Something went wrong",
                "error" => $e->getMessage()
            ], 500);
        }

    }


}
