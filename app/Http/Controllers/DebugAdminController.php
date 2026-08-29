<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DebugAdminController extends Controller
{
    public function __invoke(Request $request)
    {
        $admin = \App\Models\User::find(868);
        auth()->login($admin);

        $pages = [
            'admin.dashboard' => 'admin dashboard',
            'admin.teachers.index' => 'daftar guru',
            'admin.subscriptions.index' => 'langganan',
            'admin.announcements.form' => 'pengumuman',
        ];

        $results = [];
        foreach ($pages as $routeName => $label) {
            $route = app('router')->getRoutes()->getByName($routeName);
            if (!$route) {
                $results[$label] = "ROUTE_NOT_FOUND: $routeName";
                continue;
            }
            $req = Request::create($route->uri(), 'GET');
            $req->setUserResolver(fn() => $admin);
            try {
                $ctrl = app()->handle($req);
                $results[$label] = "OK";
            } catch (\Throwable $e) {
                $results[$label] = "ERROR: {$e->getMessage()} at ".basename($e->getFile()).":{$e->getLine()}";
            }
        }
        auth()->logout();

        return response()->json($results, 200, [], JSON_PRETTY_PRINT);
    }
}
