package at.rueckgr.android.ipwe;

import java.util.ArrayList;
import java.util.List;

import android.app.Activity;
import android.content.Intent;
import android.os.Bundle;
import android.util.Log;
import android.view.Menu;
import android.widget.ListView;
import android.widget.Toast;
import at.rueckgr.android.ipwe.data.Status;
import at.rueckgr.android.ipwe.data.Value;

public class OverviewActivity extends Activity implements InformantCallback {

    private static final String TAG = "OverviewActivity";

	@Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_overview);
        
        Informant.getInstance().addCallback(new OverviewHandler(this));

        // TODO make a singleton out of this service?
        Intent pollServiceIntent = new Intent(this, PollService.class);
        startService(pollServiceIntent);
    }

    @Override
    public boolean onCreateOptionsMenu(Menu menu) {
        getMenuInflater().inflate(R.menu.activity_overview, menu);
        return true;
    }

	// @Override
	public void notify(Status status) {
		// TODO
//		Context context = getApplicationContext();
		CharSequence text = "Hello toast!";
		int duration = Toast.LENGTH_SHORT;

		Toast toast = Toast.makeText(this, text, duration);
		toast.show();
		
		Log.d(TAG, "Notification received!");
		
		List<Value> values = new ArrayList<Value>();
		values.add(new Value());
		values.add(new Value());
		values.add(new Value());
		
		// TODO rename saa
		// TODO rename listView1
        StatusArrayAdapter saa = new StatusArrayAdapter(this, R.layout.overview_list_item, values);
	    ((ListView)findViewById(R.id.listView1)).setAdapter(saa);
	}
	
}
