@extends('adminlte::page')

@section('title', 'Roles with Permissions')

@section('content_header')
    <div class="d-flex justify-content-between">
        <h1 class="m-0 text-dark">Roles with Permissions</h1>
        <a href="{{ route('role.permission.create') }}" class="btn btn-sm btn-success"><i class="fa fa-plus mr-2"></i>Assign Permissions to Role</a>
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
                                @foreach ($roles as $k => $item)
                                    <tr class="">
                                        <td scope="row">{{ $k+1 }}</td>
                                        <td>{{ $item->name }}</td>
                                        <td>
                                            @foreach ($item->permissions as $perm)
                                                <span class="badge badge-info">{{ $perm->name }}</span>
                                            @endforeach
                                        </td>
                                        <td>
                                            <a href="{{ route('role.permission.edit', $item->id) }}" class="btn btn-xs btn-warning"><i class="fa fa-edit"></i></a>
                                            <button type="button" data-id="{{ $item->id }}" data-url="{{ route('role.permission.delete', ['id' => $item->id]) }}" data-toggle="tooltip" data-placement="top" title="Delete Permission" class="btn btn-xs btn-danger btn-delete-permission"><i class="fa fa-trash"></i></button>
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