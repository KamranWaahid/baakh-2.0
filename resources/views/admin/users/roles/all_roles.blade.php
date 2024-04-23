@extends('adminlte::page')

@section('title', 'Roles')

@section('content_header')
    <h1 class="m-0 text-dark">Roles</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-8">
            <div class="card">
                <div class="card-body">
                    <table id="permissionTable" class="table table-hover">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Role</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($roles as $k => $role)
                                <tr class="">
                                    <td scope="row">{{ $k+1 }}</td>
                                    <td>{{ $role->name }}</td>
                                    <td>
                                        <a href="{{ route('role.edit', $role->id) }}" class="btn btn-xs btn-warning"><i class="fa fa-edit"></i></a>
                                        <button type="button" data-id="{{ $role->id }}" data-url="{{ route('role.delete', ['id' => $role->id]) }}" data-toggle="tooltip" data-placement="top" title="Delete Role" class="btn btn-xs btn-danger btn-delete-role"><i class="fa fa-trash"></i></button>
                                    </td>
                                 
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-4">
            <div class="card card-body">
                <form action="{{ route('role.store') }}" method="post">
                    @csrf
                    <div class="form-group">
                        <label for="name">Role</label>
                        <input type="text" name="name" class="form-control" placeholder="Role Name" autocomplete="off">
                    </div>
                    <button type="submit" class="btn btn-sm btn-success"><i class="fa fa-save mr-2"></i> Save</button>
                </form>
            </div>
        </div>
    </div>
@stop

@section('plugins.Datatables', true)

@section('js')
<script>
    $(function () {
        $("#permissionTable").DataTable({
            "responsive": true,
            "autoWidth": false,
        });

        _delete('role', 'Role', true)
    })
</script>
@endsection
