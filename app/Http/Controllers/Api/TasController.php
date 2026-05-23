<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tas;
use App\Models\FotoTas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Cloudinary\Cloudinary;

class TasController extends Controller
{
    public function index(Request $request)
    {
        $query = Tas::with('photos');

        if ($request->filled('kode_tas')) {

            $query->where(
                'kode_tas',
                'like',
                '%' . $request->kode_tas . '%'
            );
        }

        return response()->json(
            $query->latest()->paginate(10)
        );
    }

    public function store(Request $request)
    {
        $tas = Tas::create([
            'kode_tas' => $request->kode_tas,
            'nama_tas' => $request->nama_tas,
            'kategori' => $request->kategori
        ]);

        Log::info($request->all());

        if ($request->hasFile('foto')) {

            $files = $request->file('foto');

            if (!is_array($files)) {
                $files = [$files];
            }

            foreach ($files as $file) {

                $namaFile =
                    uniqid().'_'.$file->getClientOriginalName();

                $file->move(
                    public_path('uploads'),
                    $namaFile
                );

                FotoTas::create([
                    'tas_id' => $tas->id,
                    'foto' => $namaFile
                ]);
            }
        }

        return response()->json(
            $tas->load('photos')
        );
    }

    public function show($id)
    {
        $tas = Tas::with('photos')->findOrFail($id);

        return response()->json($tas);
    }

    public function destroy($id)
    {
        $tas = Tas::findOrFail($id);

        $tas->delete();

        return response()->json([
            'message' => 'Tas berhasil dihapus'
        ]);
    }
    

    public function update(Request $request, $id)
    {
        $tas = Tas::findOrFail($id);

        $tas->update([

            'kode_tas' => $request->kode_tas,

            'nama_tas' => $request->nama_tas,

            'kategori' => $request->kategori
        ]);

        return response()->json([
            'message' => 'Berhasil update'
        ]);
    }


    public function addPhoto(Request $request, $id)
    {
        try {

            $tas = Tas::findOrFail($id);

            if (!$request->hasFile('foto')) {

                return response()->json([
                    'message' => 'No file uploaded'
                ], 400);
            }

            $cloudinary = new Cloudinary([
                'cloud' => [
                    'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                    'api_key'    => env('CLOUDINARY_API_KEY'),
                    'api_secret' => env('CLOUDINARY_API_SECRET'),
                ],
                'url' => [
                    'secure' => true
                ]
            ]);

            $files = $request->file('foto');

            if (!is_array($files)) {
                $files = [$files];
            }

            foreach ($files as $file) {

                $upload = $cloudinary
                    ->uploadApi()
                    ->upload(
                        $file->getRealPath(),
                        [
                            'folder' => 'tas'
                        ]
                    );

                $url = $upload['secure_url'];

                $photo = FotoTas::create([
                    'tas_id' => $tas->id,
                    'foto' => $url
                ]);

                Log::info($photo);
            }

            return response()->json([
                'message' => 'Foto berhasil ditambah'
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function deletePhoto($id)
    {
        $photo = FotoTas::findOrFail($id);

        $photo->delete();

        return response()->json([
            'message' => 'Foto dihapus'
        ]);
    }
}
