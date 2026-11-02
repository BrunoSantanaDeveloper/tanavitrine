<?php

declare(strict_types=1);

namespace App\Services;

use Throwable;
use RuntimeException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class HomeHeroPoster
{
    public function generate(string $videoPath): string
    {
        $disk = Storage::disk('public');
        $posterPath = 'hero/posters/'.hash('sha256', $videoPath).'.jpg';

        if ($disk->exists($posterPath)) {
            return $posterPath;
        }

        $temporaryPath = 'hero/posters/'.Str::uuid().'.jpg';

        try {
            if (! str_starts_with($videoPath, 'hero/videos/') || str_contains($videoPath, '..') || ! $disk->exists($videoPath)) {
                throw new RuntimeException('Hero video not found.');
            }

            $disk->makeDirectory('hero/posters');
            $result = Process::path(base_path())->timeout(30)->run([
                config('hero.ffmpeg_binary'),
                '-nostdin', '-hide_banner', '-loglevel', 'error', '-y',
                '-i', $disk->path($videoPath),
                '-map', '0:v:0', '-frames:v', '1', '-an',
                '-vf', "scale=w='min(1600,iw)':h=-2",
                '-q:v', '3', $disk->path($temporaryPath),
            ]);

            if ($result->failed() || ! $disk->exists($temporaryPath) || $disk->size($temporaryPath) === 0) {
                throw new RuntimeException('Could not extract the first video frame. '.$result->errorOutput());
            }

            if (! $disk->move($temporaryPath, $posterPath)) {
                throw new RuntimeException('Could not store the video poster.');
            }

            return $posterPath;
        } catch (Throwable $exception) {
            Log::warning('Could not generate hero video poster.', ['error' => $exception->getMessage()]);

            throw ValidationException::withMessages([
                'data.video_path' => 'Não foi possível gerar a imagem inicial do vídeo. Verifique se o arquivo é válido e se o FFmpeg está disponível no servidor.',
            ]);
        } finally {
            $disk->delete($temporaryPath);
        }
    }
}
