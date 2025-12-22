<?php

declare(strict_types=1);

namespace Arbify\Http\Controllers\Web\Project;

use Sopamo\LaravelFilepond\Filepond;
use Arbify\Arb\Importer\ArbImporter;
use Arbify\Arb\Importer\ImportException;
use Arbify\Http\Controllers\BaseController;
use Arbify\Http\Requests\ImportToProject;
use Arbify\Models\Project;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\File;

class ImportController extends BaseController
{
    public function __construct()
    {
        $this->middleware('verified');
        $this->authorizeResource(Project::class);
    }

    protected function resourceAbilityMap(): array
    {
        return [
            'show' => 'import',
            'upload' => 'import',
        ];
    }

    public function show(Project $project): View
    {
        return view('projects.import', [
            'project' => $project,
        ]);
    }

    public function upload(
        ImportToProject $request,
        Project $project,
        Filepond $filepond,
        ArbImporter $importer
    ): Response {
        \Log::info('Import upload started', [
            'project_id' => $project->id,
            'user_id' => $request->user()->id,
            'request_data' => $request->all()
        ]);

        $fileIds = $request->input('files', []);
        $overrideMessageValues = (bool)$request->input('override_message_values');

        \Log::info('Processing files', [
            'file_ids' => $fileIds,
            'override' => $overrideMessageValues
        ]);

        foreach ($fileIds as $i => $serverId) {
            $file = null;
            try {
                \Log::info("Processing file #{$i}", ['server_id' => $serverId]);

                $filePath = $filepond->getPathFromServerId($serverId);
                \Log::info('FilePond path retrieved', ['path' => $filePath]);

                $disk = config('filepond.temporary_files_disk', 'local');
                $fullPath = \Illuminate\Support\Facades\Storage::disk($disk)->path($filePath);
                
                \Log::info('Full file path', [
                    'disk' => $disk,
                    'full_path' => $fullPath,
                    'exists' => file_exists($fullPath),
                    'size' => file_exists($fullPath) ? filesize($fullPath) : 0
                ]);

                // Check if file exists
                if (!file_exists($fullPath)) {
                    \Log::error('File not found', ['path' => $fullPath]);
                    throw new ImportException("Uploaded file not found. Please try uploading again.");
                }

                $fileSize = filesize($fullPath);
                if ($fileSize === 0) {
                    \Log::error('File is empty', ['path' => $fullPath]);
                    throw new ImportException("Uploaded file is empty. Please try uploading again.");
                }
                
                // Get original filename from FilePond metadata or use the stored filename
                $originalName = basename($filePath);
                \Log::info('File details', [
                    'original_name' => $originalName,
                    'size' => $fileSize,
                    'mime' => mime_content_type($fullPath)
                ]);
                
                // Create UploadedFile instance from the stored file
                $file = new UploadedFile(
                    $fullPath,
                    $originalName,
                    mime_content_type($fullPath),
                    null,
                    true
                );
                
                \Log::info('Starting import for file', ['name' => $originalName]);
                $importer->import($project, $file, $request->user(), $overrideMessageValues);
                \Log::info('Import completed successfully', ['name' => $originalName]);
            } catch (ImportException $e) {
                \Log::error('Import exception occurred', [
                    'file_index' => $i,
                    'server_id' => $serverId,
                    'message' => $e->getMessage(),
                    'solution' => $e->getSolution(),
                    'trace' => $e->getTraceAsString()
                ]);

                $fileName = $file ? $file->getClientOriginalName() : $serverId;
                return back()
                    ->withInput()
                    ->withErrors([
                        "files.$i" => sprintf(
                            '<p class="mb-1"><b>%s:</b> %s</p><p class="mb-0 text-secondary">%s</p>',
                            $fileName,
                            $e->getMessage(),
                            $e->getSolution()
                        )
                    ]);
            } catch (\Exception $e) {
                \Log::error('Unexpected exception during import', [
                    'file_index' => $i,
                    'server_id' => $serverId,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }
        }

        \Log::info('All files imported successfully');
        return redirect()->route('messages.index', $project)
            ->with('success', 'File(s) successfully imported.');
    }
}
