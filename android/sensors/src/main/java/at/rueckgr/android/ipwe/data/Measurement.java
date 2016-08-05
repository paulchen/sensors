package at.rueckgr.android.ipwe.data;

import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.Date;

import org.w3c.dom.DOMException;
import org.w3c.dom.Node;

public class Measurement {
	private final float measurement;
	private final Date date;
	private final State state;
	private final Value value;
	
	// TODO use an enum type for this
	private final String type;
	
	public Measurement(Node node, Value value) throws SensorsException {
		this.value = value;

		SimpleDateFormat sdf = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss");
		try {
			measurement = Float.parseFloat(node.getAttributes().getNamedItem("value").getTextContent());
			type = node.getAttributes().getNamedItem("type").getTextContent();
			
			if(node.getAttributes().getNamedItem("timestamp") != null) {
				date = sdf.parse(node.getAttributes().getNamedItem("timestamp").getTextContent());
			}
			else {
				date = null;
			}
			if(node.getAttributes().getNamedItem("state") != null) {
				state = value.getApplication().getState(node.getAttributes().getNamedItem("state").getTextContent());
			}
			else {
				state = null;
			}
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
		return value.getType().getFormat().replace("%s", String.valueOf(measurement));
	}
	
	public CharSequence getTimestampString() {
		SimpleDateFormat sdf = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
		return sdf.format(date);
	}

	public int getStateCount(State state) {
		return state.equals(this.state) ? 1 : 0;
	}

	public String getType() {
		return type;
	}
}
