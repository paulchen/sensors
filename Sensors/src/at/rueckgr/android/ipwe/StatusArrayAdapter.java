package at.rueckgr.android.ipwe;

import java.util.List;

import android.content.Context;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ArrayAdapter;
import android.widget.TextView;
import at.rueckgr.android.ipwe.data.Measurement;
import at.rueckgr.android.ipwe.data.Sensor;
import at.rueckgr.android.ipwe.data.Value;

public class StatusArrayAdapter extends ArrayAdapter<Measurement> {

	private List<Measurement> measurement;

	// TODO remove first and second parameter?
	public StatusArrayAdapter(Context context, int textViewResourceId,
			List<Measurement> measurement) {
		super(context, textViewResourceId, measurement);
		this.measurement = measurement;
	}

	@Override
	public int getCount() {
		return measurement.size();
	}

	@Override
	public Measurement getItem(int position) {
		return measurement.get(position);
	}

	@Override
	public View getView(int position, View convertView, ViewGroup parent) {

		View row = convertView;
		Measurement measurement = getItem(position);
		Value value = measurement.getValue();
		Sensor sensor = value.getSensor();
		// Status status = sensor.getStatus();
		
		if (row == null) {
			// Inflate rows
			LayoutInflater inflater = (LayoutInflater) this.getContext().getSystemService(Context.LAYOUT_INFLATER_SERVICE);
			row = inflater.inflate(R.layout.overview_list_item, parent, false);
		}
		
		
		// TODO
		((TextView) row.findViewById(R.id.sensor_name)).setText(sensor.getName());
		((TextView) row.findViewById(R.id.value_timestamp)).setText(measurement.getTimestampString());
		((TextView) row.findViewById(R.id.sensor_value)).setText(String.valueOf(measurement.getMeasurement()));
		((TextView) row.findViewById(R.id.sensor_status_color)).setText(measurement.getState());

		return row;
	}
}
