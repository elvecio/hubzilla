<div class="app-container">
	<div class="app-detail{{if $deleted}} app-deleted{{/if}}">
		<a href="{{$app.url}}" {{if $ap.target}}target="{{$ap.target}}" {{/if}}{{if $app.desc}}title="{{$app.desc}}{{if $app.price}} ({{$app.price}}){{/if}}"{{else}}title="{{$app.name}}"{{/if}}>{{if $icon}}<i class="app-icon fa fa-fw fa-{{$icon}}"></i>{{else}}<img src="{{$app.photo}}" width="80" height="80" />{{/if}}
			<div class="app-name" style="text-align:center;">{{$app.name}}</div>
		</a>
	</div>
	{{if $app.type !== 'system'}}
	{{if $purchase}}
	<div class="app-purchase">
		<a href="{{$app.page}}" class="btn btn-default" title="{{$purchase}}" ><i class="fa fa-external"></i></a>
	</div>
	{{/if}}
	{{if $install || $update || $delete }}
	<div class="app-tools">
		<form action="{{$hosturl}}appman" method="post">
		<input type="hidden" name="papp" value="{{$app.papp}}" />
		{{if $install}}<button type="submit" name="install" value="{{$install}}" class="btn btn-default btn-xs" title="{{$install}}" ><i class="fa fa-arrow-circle-o-down" ></i></button>{{/if}}
		{{if $edit}}<input type="hidden" name="appid" value="{{$app.guid}}" /><button type="submit" name="edit" value="{{$edit}}" class="btn btn-default btn-xs" title="{{$edit}}" ><i class="fa fa-pencil" ></i></button>{{/if}}
		{{if $delete}}<button type="submit" name="delete" value="{{if $deleted}}{{$undelete}}{{else}}{{$delete}}{{/if}}" class="btn btn-default btn-xs" title="{{if $deleted}}{{$undelete}}{{else}}{{$delete}}{{/if}}" ><i class="fa fa-trash-o drop-icons"></i></button>{{/if}}
		<button type="submit" name="feature" value="feature" class="btn btn-default btn-xs" ><i class="fa fa-star"{{if $featured}} style="color: gold"{{/if}}></i></button>
		</form>
	</div>
	{{/if}}
	{{/if}}
</div>

