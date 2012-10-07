package at.rueckgr.android.ipwe.data;

import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.Date;

import org.w3c.dom.DOMException;
import org.w3c.dom.Node;

import at.rueckgr.android.ipwe.CommonData;

public class Measurement {
	private float measurement;
	private Date date;
	private State state;
	private Value value;
	
	public Measurement(Node node, Value value) {
		this.value = value;
		
		// TODO possible NumberFormatException
		// TODO possibly null
		measurement = Float.parseFloat(node.getAttributes().getNamedItem("value").getTextContent());
		// TODO possibly null
		SimpleDateFormat sdf = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss");
		try {
			date = sdf.parse(node.getAttributes().getNamedItem("timestamp").getTextContent());
		} catch (DOMException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		} catch (ParseException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		}
		CommonData commonData = CommonData.getInstance();
		// TODO possibly null
		state = commonData.getState(node.getAttributes().getNamedItem("state").getTextContent());		
	}

	public float getMeasurement() {
		return measurement;
	}

	public Date getDate() {
		return date;
	}

	public State getState() {
		return state;
	}
	
	public String toString() {
		// return "[Measurement:value=" + value + "state=" + state + "]";
		SimpleDateFormat sdf = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss");
		return "[Measurement:timestamp=" + sdf.format(date) + ";value=" + value + "state=" + state + "]";
	}

	public Value getValue() {
		return value;
	}

	public String getFormattedMeasurement() {
		return value.getFormat().replace("%s", String.valueOf(measurement));
	}
	
	public CharSequence getTimestampString() {
		SimpleDateFormat sdf = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
		return sdf.format(date);
	}
}
