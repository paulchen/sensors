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
	
	public Measurement(Node node, Value value) throws SensorsException {
		this.value = value;
		CommonData commonData = CommonData.getInstance();

		SimpleDateFormat sdf = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss");
		try {
			measurement = Float.parseFloat(node.getAttributes().getNamedItem("value").getTextContent());
			date = sdf.parse(node.getAttributes().getNamedItem("timestamp").getTextContent());
			state = commonData.getState(node.getAttributes().getNamedItem("state").getTextContent());		
		}
		catch (NumberFormatException e) {
			throw new SensorsException(e);
		}
		catch (NullPointerException e) {
			throw new SensorsException(e);
		}
		catch (DOMException e) {
			throw new SensorsException(e);
		}
		catch (ParseException e) {
			throw new SensorsException(e);
		}
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
