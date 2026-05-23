<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tas;
use App\Models\FotoTas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

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

        if ($request->file('foto')) {

            $files = $request->file('foto');

            foreach ($files as $file) {

                $uploadedFile = Cloudinary::upload(
                    $file->getRealPath(),
                    [
                        'folder' => 'tas'
                    ]
                );

                $url = $uploadedFile->getSecurePath();

                FotoTas::create([
                    'tas_id' => $tas->id,
                    'foto' => $url
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
        $tas = Tas::findOrFail($id);

        if ($request->hasFile('foto')) {

            foreach ($request->file('foto') as $file) {

                $uploadedFile = Cloudinary::upload(
                    $file->getRealPath(),
                    [
                        'folder' => 'tas'
                    ]
                );

                $url = $uploadedFile
                    ->getSecurePath();

                FotoTas::create([

                    'tas_id' => $tas->id,

                    'foto' => $url
                ]);
            }
        }

        return response()->json([
            'message' => 'Foto berhasil ditambah'
        ]);
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
