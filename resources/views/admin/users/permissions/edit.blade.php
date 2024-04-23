@extends('adminlte::page')

@section('title', 'Create Permission')

@section('content_header')
    <div class="d-flex justify-content-between">
        <h1 class="m-0 text-dark">Create Permissions</h1>
        <a href="{{ route('permissions.index') }}" class="btn btn-sm btn-warning"><i class="fa fa-list mr-2"></i>View All Permissions</a>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('permissions.update', $data->id) }}" method="post">
                        @csrf
                        @method('put')
                        <div class="row">
                            <div class="form-group col-6">
                                <label for="name">Permissino Name</label>
                                <input type="text" name="name" value="{{ old('name', $data->name) }}" class="form-control" placeholder="for example : poets.edit" autocomplete="off">
                            </div>
                            <div class="form-group col-6">
                                <label for="name">Group</label>
                                <select name="group_name" class="form-control" id="group_name">
                                    <option value="">Select Group</option>
                                    @foreach ($groups as $item)
                                        <option value="{{ $item }}" @if ($item == $data->group_name)
                                            selected
                                        @endif>{{ Str::ucfirst($item) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-block btn-success"><i class="fa fa-save mr-2"></i>Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop
