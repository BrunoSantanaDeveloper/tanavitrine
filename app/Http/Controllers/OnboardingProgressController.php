<?php

namespace App\Http\Controllers;

use App\Models\ClinicMedia;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class OnboardingProgressController extends Controller
{
    /**
     * Salvar progresso do onboarding
     */
    public function save(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'step' => 'required|integer|min:1|max:6',
            'data' => 'required|array',
        ]);

        $user->update([
            'onboarding_step' => $validated['step'],
            'onboarding_data' => array_merge(
                $user->onboarding_data ?? [],
                $validated['data']
            ),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Upload de foto da clínica
     */
    public function uploadPhoto(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = auth()->user();
        $file = $request->file('photo');

        // Armazenar foto
        $path = $file->store("clinic_photos/{$user->id}", 'public');

        // Criar registro no banco
        $media = ClinicMedia::create([
            'user_id' => $user->id,
            'team_id' => $user->current_team_id,
            'type' => 'photo',
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'order' => ClinicMedia::where('user_id', $user->id)->where('type', 'photo')->count(),
        ]);

        return response()->json([
            'success' => true,
            'media_id' => $media->id,
            'url' => Storage::url($path),
        ]);
    }

    /**
     * Upload de vídeo institucional
     */
    public function uploadVideo(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'video' => 'required|mimes:mp4,mov,avi|max:51200', // 50MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = auth()->user();
        $file = $request->file('video');

        // Armazenar vídeo
        $path = $file->store("clinic_videos/{$user->id}", 'public');

        // Criar registro no banco
        $media = ClinicMedia::create([
            'user_id' => $user->id,
            'team_id' => $user->current_team_id,
            'type' => 'video',
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'order' => 0,
        ]);

        return response()->json([
            'success' => true,
            'media_id' => $media->id,
            'url' => Storage::url($path),
        ]);
    }

    /**
     * Deletar mídia
     */
    public function deleteMedia(Request $request, int $mediaId): JsonResponse
    {
        $user = auth()->user();

        $media = ClinicMedia::where('id', $mediaId)
            ->where('user_id', $user->id)
            ->first();

        if (!$media) {
            return response()->json([
                'success' => false,
                'message' => 'Mídia não encontrada',
            ], 404);
        }

        // Deletar arquivo do storage
        if (Storage::disk('public')->exists($media->file_path)) {
            Storage::disk('public')->delete($media->file_path);
        }

        // Deletar registro do banco
        $media->delete();

        return response()->json(['success' => true]);
    }
}
