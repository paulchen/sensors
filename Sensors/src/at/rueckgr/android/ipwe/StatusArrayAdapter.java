package at.rueckgr.android.ipwe;

import java.util.List;

import android.content.Context;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ArrayAdapter;
import android.widget.TextView;
import at.rueckgr.android.ipwe.data.Value;

public class StatusArrayAdapter extends ArrayAdapter<Value> {

	private List<Value> sensors;

	// TODO remove first and second parameter
	public StatusArrayAdapter(Context context, int textViewResourceId,
			List<Value> sensors) {
		super(context, textViewResourceId, sensors);
		this.sensors = sensors;
	}

	@Override
	public int getCount() {
		return sensors.size();
	}

	@Override
	public Value getItem(int position) {
		return sensors.get(position);
	}

	@Override
	public View getView(int position, View convertView, ViewGroup parent) {

		View row = convertView;
		// Value value = getItem(position);
		
		if (row == null) {
			// Inflate rows
			LayoutInflater inflater = (LayoutInflater) this.getContext().getSystemService(Context.LAYOUT_INFLATER_SERVICE);
			row = inflater.inflate(R.layout.overview_list_item, parent, false);
		}
		
		// TODO
		((TextView) row.findViewById(R.id.sensor_name)).setText("sensor name");
		((TextView) row.findViewById(R.id.value_timestamp)).setText("timestamp");
		((TextView) row.findViewById(R.id.sensor_value)).setText("value");
		((TextView) row.findViewById(R.id.sensor_status_color)).setText("color");

		return row;
	}
}
