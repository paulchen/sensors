package at.rueckgr.android.ipwe;

import java.util.ArrayList;
import java.util.List;

import android.app.Activity;
import android.content.Intent;
import android.os.Bundle;
import android.util.Log;
import android.view.Menu;
import android.view.MenuItem;
import android.widget.ListView;
import android.widget.Toast;
import at.rueckgr.android.ipwe.data.Measurement;
import at.rueckgr.android.ipwe.data.Sensor;
import at.rueckgr.android.ipwe.data.Status;
import at.rueckgr.android.ipwe.data.Value;

public class OverviewActivity extends Activity implements InformantCallback {
    private static final String TAG = "OverviewActivity";
    private CommonData commonData;
    // TODO move to CommonData
	private static Status status;
    
    public OverviewActivity() {
        commonData = CommonData.getInstance();
    }
    
	@Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_overview);
        
        Informant.getInstance().addCallback(new OverviewHandler(this));

        if(commonData.pollServiceIntent == null) {
        	commonData.pollServiceIntent = new Intent(this, PollService.class);
        	startService(commonData.pollServiceIntent);
        }
        else {
        	notify(status);
//        	commonData.pollService.triggerUpdate();
        }
    }

    @Override
    public boolean onCreateOptionsMenu(Menu menu) {
        getMenuInflater().inflate(R.menu.activity_overview, menu);
        return true;
    }

	// @Override
	public void notify(Status status) {
		OverviewActivity.status = status;
		// TODO
//		Context context = getApplicationContext();
		
		// TODO suppress if update untriggered
		CharSequence text = "Sensors updated";
		int duration = Toast.LENGTH_SHORT;

		Toast toast = Toast.makeText(this, text, duration);
		toast.show();
		
		Log.d(TAG, "Notification received");
		
		List<Measurement> measurements = new ArrayList<Measurement>();
		for(Sensor sensor : status.getSensors()) {
			for(Value value : sensor.getValues()) {
				measurements.addAll(value.getMeasurements());
			}
		}
		
		// TODO rename saa
		// TODO rename listView1
        StatusArrayAdapter saa = new StatusArrayAdapter(this, R.layout.overview_list_item, measurements);
	    ((ListView)findViewById(R.id.listView1)).setAdapter(saa);
	}

	@Override
	public boolean onOptionsItemSelected(MenuItem item) {
		switch(item.getItemId()) {
		case R.id.menu_exit:
			// TODO
			break;
		
		case R.id.menu_settings:
			// TODO
			break;
			
		case R.id.menu_update:
			commonData.pollService.triggerUpdate();
			// TODO
			break;
		}
		
		return true;
	}
}
