<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\CeoLocation;
use Symfony\Component\HttpFoundation\Response;

class GeofenceAccessMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. BYPASS RULE: If the user is a CEO, ignore the geofence.
        // Replace 'CEO' with your actual role name or ID if different.
        if ($request->user() && $request->user()->role === 'CEO') {
            return $next($request);
        }

        // 2. FETCH SAFE ZONE: Find the designated safe zone for this user.
        $safeZone = CeoLocation::where('user_id', auth()->id())->latest()->first();

        // If no safe zone is set for a non-CEO user, we block access for security.
        if (!$safeZone) {
            return response()->json(['message' => 'No authorized safe zone defined for your SCM account.'], 403);
        }

        // 3. CAPTURE HEADERS: These must be sent from your Vue layout.
        $currentLat = $request->header('X-User-Lat');
        $currentLng = $request->header('X-User-Lng');

        if (!$currentLat || !$currentLng) {
            return response()->json(['message' => 'GPS Tracking Required: Access to SCM is restricted to authorized zones.'], 403);
        }

        // 4. DISTANCE MATH (Haversine Formula)
        $distance = $this->calculateDistance(
            $safeZone->latitude, $safeZone->longitude,
            $currentLat, $currentLng
        );

        // 5. ENFORCE RANGE: If they are outside the set meter radius, kill the request.
        if ($distance > $safeZone->range_radius) {
            return response()->json([
                'message' => 'Access Denied: You are ' . round($distance) . 'm away from your authorized work zone.',
                'status' => 'outside_range'
            ], 403);
        }

        return $next($request);
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // Meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        return $earthRadius * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }
}