package at.rueckgr.android.ipwe.data;

import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.Date;

import org.w3c.dom.DOMException;
import org.w3c.dom.Node;

public class Measurement {
	private float value;
	private Date date;
	// TODO replace by class/enum
	private String state;
	
	public Measurement(Node node) {
		// TODO possible NumberFormatException
		// TODO possibly null
		value = Float.parseFloat(node.getAttributes().getNamedItem("value").getTextContent());
		// TODO possibly null
		// TODO fuck off Java
		SimpleDateFormat sdf = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ssXXX");
		try {
			date = sdf.parse(node.getAttributes().getNamedItem("timestamp").getTextContent());
		} catch (DOMException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		} catch (ParseException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		}		
		// TODO possibly null
		state = node.getAttributes().getNamedItem("state").getTextContent();		
	}

	public float getValue() {
		return value;
	}

	public Date getDate() {
		return date;
	}

	public String getState() {
		return state;
	}
	
	public String toString() {
		SimpleDateFormat sdf = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ssXXX");
		return "[Measurement:timestamp=" + sdf.format(date) + ";value=" + value + "state=" + state + "]";
	}
}