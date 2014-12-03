<?php if(isset($data['results'])): ?>
<div class="row">
    <div class="col-md-12">
        <h3 class="page-header">Results</h3>
        <table class="table table-condensed table-hover">
            <colgroup>
                <col class="col-md-4">
                <col class="col-md-4">
                <col class="col-md-4">
            </colgroup>
            <thead>
                <tr>
                    <th>Twitter Username</th>
                    <th>Twitter Id</th>
                    <th>Retweeted Status Id</th>
                </tr>
            </thead>
            <tbody>
                <?= $data['results']; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<div class="row">
	<div class="col-md-12">
		<h3 class="page-header">Find a Winner Settings</h3>
		<form class="form form-horizontal" action="/winner" method="POST" role="form">
            <div class="form-group">
                <label for="winnernumber" class="col-sm-2 control-label">Number of Entries to Pick</label>
                <div class="col-sm-10">
                    <input type="number" name="winnernumber" min="1" max="100" class="form-control" placeholder="<?= $data['number_default']; ?>">
                </div>
            </div>
			<div class="form-group">
				<div class="col-sm-offset-2 col-sm-10">
      				<div class="checkbox">
        				<label>
        					<input type="checkbox" name="follower" value="1" <?= ($data['follower_default'] == 1 ? 'checked="checked"' : '') ?>> Require Follow of Twitter Account
        				</label>
        			</div>
        			<div class="checkbox">
        				<label>
        					<input type="checkbox" name="previouswinner" value="1" <?= ($data['winner_default'] == 1 ? 'checked="checked"' : '') ?>> Remove Previous Winners
        				</label>
        			</div>
        			<div class="checkbox">
        				<label>
        					<input type="checkbox" name="exclude" value="1" <?= ($data['exclude_default'] == 1 ? 'checked="checked"' : '') ?>> Remove Excluded Users
        				</label>
        			</div>
        		</div>
        	</div>
            <div class="form-group">
                <div class="col-sm-offset-2 col-sm-10">
                    <button type="submit" class="btn btn-default">Find Winners</button>
                </div>
            </div>
        </form>
    </div>
</div>