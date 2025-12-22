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
        $fileIds = $request->input('files', []);
        $overrideMessageValues = (bool)$request->input('override_message_values');

        foreach ($fileIds as $i => $serverId) {
            $file = null;
            try {
                $filePath = $filepond->getPathFromServerId($serverId);
                $disk = config('filepond.temporary_files_disk', 'local');
                $fullPath = \Illuminate\Support\Facades\Storage::disk($disk)->path($filePath);
                
                // Check if file exists
                if (!file_exists($fullPath)) {
                    throw new ImportException("Uploaded file not found. Please try uploading again.");
                }

                $fileSize = filesize($fullPath);
                if ($fileSize === 0) {
                    throw new ImportException("Uploaded file is empty. Please try uploading again.");
                }
                
                // Get original filename from FilePond metadata or use the stored filename
                $originalName = basename($filePath);
                
                // Create UploadedFile instance from the stored file
                $file = new UploadedFile(
                    $fullPath,
                    $originalName,
                    mime_content_type($fullPath),
                    null,
                    true
                );
                
                $importer->import($project, $file, $request->user(), $overrideMessageValues);
            } catch (ImportException $e) {
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
            }
        }

        return redirect()->route('messages.index', $project)
            ->with('success', 'File(s) successfully imported.');
    }
}
