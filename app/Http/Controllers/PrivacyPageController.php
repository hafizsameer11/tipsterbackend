<?php

namespace App\Http\Controllers;

use App\Models\PrivacyPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PrivacyPageController extends Controller
{
    //
    public function store(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'title' => 'required',
            'url' => 'required'
        ]);
        if ($validatedData->fails()) {
            return response()->json(['error' => $validatedData->errors()->all()]);
        }
        $link = new PrivacyPage();
        $link->title = $request->title;
        $link->url = $request->url;
        $link->save();
        return response()->json(['success' => 'Link added successfully', 'data' => $link]);
    }
    public function index()
    {
        $privacy = PrivacyPage::first();
        return response()->json(['data' => $privacy]);
    }
}
