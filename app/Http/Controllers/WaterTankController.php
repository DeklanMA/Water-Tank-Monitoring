<?php

namespace App\Http\Controllers;

use App\Models\Mpompa;
use App\Models\Watertank;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class WaterTankController extends Controller
{
  public function statuskedalaman()
  {
    $latestWaterTank = Watertank::latest()->first();

    if ($latestWaterTank) {
      $depthStatus = $latestWaterTank->kedalaman;
      return response()->json(['status' => 'success', 'depth_status' => $depthStatus], 200);
    } else {
      return response()->json(['status' => 'error', 'message' => 'No data available'], 404);
    }
  }


  public function suhu()
  {
    $latestWaterTank = Watertank::latest()->first();

    if ($latestWaterTank) {
      $temperature = $latestWaterTank->suhu;
      return response()->json(['status' => 'success', 'temperature' => $temperature], 200);
    } else {
      return response()->json(['status' => 'error', 'message' => 'No data available'], 404);
    }
  }






  public function getStatuspompa()
  {
    $latestPump = Mpompa::latest()->first();

    if ($latestPump) {
      $status = $latestPump->status;
      $onTime = 'N/A';
      $offTime = 'N/A';
      $fillingDuration = 'N/A';

      // Jika pompa aktif, hitung durasi waktu
      if ($status === 'AKTIF') {
        $onTime = $latestPump->on_time ?? now(); // Default value jika null
        $offTime = $latestPump->off_time ?? null; // Default value jika null

        // Jika waktu nonaktif belum di-set, set waktu nonaktif ke waktu sekarang
        if (!$offTime) {
          $latestPump->update(['off_time' => now()]);
        }

        // Hitung durasi pengisian
        $fillingDuration = Carbon::parse($onTime)->diffInSeconds($offTime);
      }

      return response()->json([
        'status' => 'success',
        'pump_status' => $status,
        'on_time' => $onTime,
        'off_time' => $offTime,
        'filling_duration' => $fillingDuration,
      ], 200);
    } else {
      return response()->json(['status' => 'error', 'message' => 'No pump data available'], 404);
    }
  }

  public function calculateVolumeFromDepth(Request $request, $watertankId)
  {
    // Tidak perlu lagi memvalidasi watertankId karena sudah diberikan sebagai parameter
    // $this->validate($request, [
    //   'watertankId' => 'required|numeric|min:0',
    // ]);

    // Ambil data Watertank berdasarkan ID
    $watertank = Watertank::find($watertankId);

    if (!$watertank) {
      return response()->json(['status' => 'error', 'message' => 'Watertank not found'], 404);
    }

    // Ambil kedalaman dari model Watertank
    $depth = $watertank->kedalaman;

    // Hitung volume menggunakan rumus silinder
    $volume = $this->calculateCylinderVolume($depth);

    return response()->json(['status' => 'success', 'volume' => $volume], 200);
  }

  private function calculateCylinderVolume($depth)
  {
    // Diameter toren (dalam sentimeter)
    $diameterToren = 400;

    // Hitung jari-jari dari diameter
    $radius = $diameterToren / 2;

    // Konversi jari-jari ke diameter
    $diameter = 2 * $radius;

    // Hitung volume silinder dalam kubik sentimeter
    $volumeCm3 = M_PI * pow(($diameter / 2), 2) * $depth;

    // Konversi volume ke liter
    $volumeLiter = $volumeCm3 / 1000;

    return $volumeLiter;
  }
}
