@foreach ($couplets as $key => $data)
            
    <tr id="{{ $key+1 }}">
        <td><?php echo $key+1; ?></td>
        
        <td class="text-center" dir="{{ $data->language->lang_dir }}">
        {!! nl2br($data->couplet_text) !!}
        </td>
        <td>
        <span class="badge bg-success p-1 rounded" data-toggle="tooltip" data-placement="top" title="Poet Name"><i class="fa fa-user mr-1"></i>{{ $data->poet->details->poet_laqab }}</span>
        @if ($data->poetry_id != '0')
            <span class="badge bg-secondary p-1 rounded"  data-toggle="tooltip" data-placement="top" title="Linked with Main Poetry" ><i class="fas fa-link"></i></span>
        @endif
        </td>
        <td>{{ $data->couplet_tags }}</td>
        <td>
        <span class="badge bg-warning p-1 rounded" data-toggle="tooltip" data-placement="top" title="Available in Language"><i class="fa fa-globe mr-1"></i>{{ $data->language->lang_title }}</span>
        </td>
        
        
        <td width="12%" class="text-center">
            <a href="{{ route('admin.couplets.edit', $data->id) }}"  data-toggle="tooltip" data-placement="top" title="Update Couplets"  class="btn btn-xs btn-warning"><i class="fa fa-edit"></i></a>
            <button type="button" data-id="{{ $data->id }}" data-url="{{ route('admin.couplets.destroy', ['id' => $data->id]) }}" data-toggle="tooltip" data-placement="top" title="Delete Couplets" class="btn btn-xs btn-danger btn-delete-couplet"><i class="fa fa-trash"></i></button>
        </td>
    </tr>
    @endforeach