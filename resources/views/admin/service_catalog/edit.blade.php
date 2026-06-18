@extends('layouts.app')

@section('title', 'Edit Master Jasa')
@section('heading', 'Edit Master Jasa')

@section('content')
    <section class="section">
        <div class="row">
            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-1">Edit Master Jasa</h4>
                        <p class="text-muted mb-0">Perubahan tidak mengubah nota historis.</p>
                    </div>

                    <div class="card-body">
                        @include('admin.service_catalog.partials.form', [
                            'action' => route('admin.services.update', ['serviceId' => $service['id']]),
                            'method' => 'PUT',
                            'submitLabel' => 'Update Jasa',
                        ])
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
