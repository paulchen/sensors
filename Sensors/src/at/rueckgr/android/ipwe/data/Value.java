package at.rueckgr.android.ipwe.data;

import java.util.ArrayList;
import java.util.List;

import org.w3c.dom.Node;
import org.w3c.dom.NodeList;

import at.rueckgr.android.ipwe.SensorsApplication;

public class Value {
	
	// TODO replace by enum/class
	private int type;
	private List<Measurement> measurements;
	private Sensor sensor;
	private String format;
	private String description;
	
	public Value(Node node, Sensor sensor) throws SensorsException {
		this.sensor = sensor;
		
		try {
			type = Integer.parseInt(node.getAttributes().getNamedItem("type").getTextContent());
			format = node.getAttributes().getNamedItem("format").getTextContent();
			description = node.getAttributes().getNamedItem("description").getTextContent();
		}
		catch (NumberFormatException e) {
			throw new SensorsException(e);
		}
		catch (NullPointerException e) {
			throw new SensorsException(e);
		}
		processNode(node);
	}

	private void processNode(Node parentNode) throws SensorsException {
		measurements = new ArrayList<Measurement>();
		NodeList nodes = parentNode.getChildNodes();
		for(int a=0; a<nodes.getLength(); a++) {
			Node node = nodes.item(a);
			if(node.getNodeType() == Node.ELEMENT_NODE && node.getNodeName().equals("measurement")) {
				measurements.add(new Measurement(node, this));
			}
		}
	}

	public int getType() {
		return type;
	}
	
	public String toString() {
		return "[Value:type=" + type + ";measurements=" + measurements.toString() + "]";
	}

	public List<Measurement> getMeasurements() {
		return measurements;
	}

	public Sensor getSensor() {
		return sensor;
	}
	
	public String getFormat() {
		return format;
	}
	
	public String getDescription() {
		return description;
	}

	public int getStateCount(State state) {
		int count = 0;
		
		for(Measurement measurement : measurements) {
			count += measurement.getStateCount(state);
		}
		
		return count;
	}

	public SensorsApplication getApplication() {
		return sensor.getApplication();
	}
}
