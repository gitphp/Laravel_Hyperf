<?php

declare(strict_types=1);

namespace App\Http\Controllers;
/**
 * 这是一个PHP封装的框架
 * @author    Sunny Yang
 * 2026-08-19 15:43:26
 * @link     www.budff.com
 */

use SunnyPHP\Http\Request;
use SunnyPHP\Http\Response;

final class HomeController
{
    public function index(Request $request): Response
    {
        return Response::json([
            'name' => 'SunnyPHP',
            'message' => 'Hello, World',
            'request_id' => $request->attribute('request_id'),
        ]);
    }
}
