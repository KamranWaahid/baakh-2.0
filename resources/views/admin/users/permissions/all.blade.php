@extends('adminlte::page')

@section('title', 'Permissions')

@section('content_header')
    <div class="d-flex justify-content-between">
        <h1 class="m-0 text-dark">Permissions</h1>
        <a href="{{ route('permissions.create') }}" class="btn btn-sm btn-success"><i class="fa fa-plus mr-2"></i>Add Permissions</a>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    Permissions
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="permissionTable" class="table table-hover">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Permission</th>
                                    <th scope="col">Group</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($permissions as $k => $p)
                                    <tr class="">
                                        <td scope="row">{{ $k+1 }}</td>
                                        <td>{{ $p->name }}</td>
                                        <td>{{ $p->group_name }}</td>
                                        <td>
                                            <a href="{{ route('permissions.edit', $p->id) }}" class="btn btn-sm btn-warning"><i class="fa fa-edit"></i></a>
                                            <button type="button" data-id="{{ $p->id }}" data-url="{{ route('permissions.delete', ['id' => $p->id]) }}" data-toggle="tooltip" data-placement="top" title="Delete Permission" class="btn btn-xs btn-danger btn-delete-permission"><i class="fa fa-trash"></i></button>
                                        </td>
                                     
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                </div>
                <div class="card-footer">

                </div>
            </div>
        </div>
    </div>
@stop

@section('plugins.Datatables', true)

@section('js')
<script>
    $(function () {
        _delete('permission', 'Permission')
        $("#permissionTable").DataTable({
            "responsive": true,
            "autoWidth": false,
        });
    })
</script>
@endsection