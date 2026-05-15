<?php

declare(strict_types=1);

namespace App\Providers;

use App\Adapters\Out\Note\DbNoteRevisionLineRowMapper;
use App\Adapters\Out\Note\DbNoteRevisionPayloadCodec;
use App\Adapters\Out\Note\DbNoteRevisionRepository;
use App\Adapters\Out\Note\DbNoteRevisionRowMapper;
use App\Ports\Out\Note\NoteRevisionReaderPort;
use App\Ports\Out\Note\NoteRevisionWriterPort;
use Illuminate\Support\ServiceProvider;

class NoteRevisionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(
            DbNoteRevisionPayloadCodec::class,
            fn (): DbNoteRevisionPayloadCodec => new DbNoteRevisionPayloadCodec()
        );

        $this->app->scoped(
            DbNoteRevisionLineRowMapper::class,
            fn (): DbNoteRevisionLineRowMapper => new DbNoteRevisionLineRowMapper(
                $this->app->make(DbNoteRevisionPayloadCodec::class),
            )
        );

        $this->app->scoped(
            DbNoteRevisionRowMapper::class,
            fn (): DbNoteRevisionRowMapper => new DbNoteRevisionRowMapper(
                $this->app->make(DbNoteRevisionLineRowMapper::class),
            )
        );

        $this->app->scoped(
            DbNoteRevisionRepository::class,
            fn (): DbNoteRevisionRepository => new DbNoteRevisionRepository(
                $this->app->make(DbNoteRevisionRowMapper::class),
                $this->app->make(DbNoteRevisionLineRowMapper::class),
            )
        );

        $this->app->scoped(
            NoteRevisionReaderPort::class,
            fn (): NoteRevisionReaderPort => $this->app->make(DbNoteRevisionRepository::class)
        );

        $this->app->scoped(
            NoteRevisionWriterPort::class,
            fn (): NoteRevisionWriterPort => $this->app->make(DbNoteRevisionRepository::class)
        );
    }
}
