<?php
namespace App\Http\Controllers;

use App\Models\FawryTransaction;
use App\Services\FawryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ApiController extends Controller
{
   
    public function sendResponse($data, $message = null, $code = 200): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $code,[],JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function sendError($data = null, $message = null, $code = 400): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data'    => $data,
        ], $code,[],JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    public function check_banned()
    {
        if (auth()->user()->status == 'banned') {
            return 'this account is banned, the ban will be lifted soon';
        } else {
            return true;
        }
    }

    

}
