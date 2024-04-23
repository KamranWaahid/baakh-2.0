@extends('adminlte::page')

@section('title', 'Roles')

@section('content_header')
    <h1 class="m-0 text-dark">Roles</h1>
@stop

@section('content')
    <div class="row">
          
        <div class="col-6">
            <div class="card card-body">
                <form action="{{ route('role.update', $data->id) }}" method="post">
                    @csrf
                    @method('put')
                    <div class="form-group">
                        <label for="name">Role</label>
                        <input type="text" name="name" value="{{ old('name', $data->name) }}" class="form-control" placeholder="Role Name" autocomplete="off">
                    </div>
                    <button type="submit" class="btn btn-sm btn-success"><i class="fa fa-save mr-2"></i> Save</button>
                </form>
            </div>
        </div>
    </div>
@stop
 