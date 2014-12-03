<div class="row">
	<div class="col-md-12">
		<h3 class="page-header">Find a Winner Settings</h3>
		<form class="form form-horizontal" action="/winner/find" method="POST" role="form">
			<div class="form-group">
				<div class="col-sm-offset-2 col-sm-10">
      				<div class="checkbox">
        				<label>
        					<input type="checkbox" name="follower" value="1" <?= ($data['follower_default'] == 1 ? 'checked="checked"' : '') ?>> Require Follow of Twitter Account
        				</label>
        			</div>
        			<div class="checkbox">
        				<label>
        					<input type="checkbox" name="previouswinner" value="1" <?= ($data['winner_default'] == 1 ? 'checked="checked"' : '') ?>> Allow Previous Winners to be Included
        				</label>
        			</div>
        			<div class="checkbox">
        				<label>
        					<input type="checkbox" name="exclude" value="1" <?= ($data['exclude_default'] == 1 ? 'checked="checked"' : '') ?>> Remove Excluded Users
        				</label>
        			</div>
        		</div>
        	</div>
        </form>
    </div>
</div>