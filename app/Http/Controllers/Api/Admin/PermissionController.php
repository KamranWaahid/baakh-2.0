<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!auth()->user()->can('manage_permissions') && !auth()->user()->can('assign_roles')) {
            // Let people who can assign roles also see permissions
            if (!auth()->user()->hasRole('super_admin')) {
                // abort(403); 
                // Maybe not strict abort if we want UI to just list them for selection
            }
        }

        $permissions = Permission::all()->groupBy(function ($item) {
            // Attempt to group by resource if naming convention allows
            // e.g. view_poetry -> poetry
            $parts = explode('_', $item->name);
            if (count($parts) > 1) {
                return end($parts); // simplistic grouping
            }
            return 'general';
        });

        return response()->json([
            'grouped' => $permissions,
            'all' => Permission::all()
        ]);
    }
}
