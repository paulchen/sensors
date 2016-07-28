package at.rueckgr.android.ipwe.data;

import java.util.ArrayList;
import java.util.List;

import org.w3c.dom.Node;
import org.w3c.dom.NodeList;

import at.rueckgr.android.ipwe.SensorsApplication;

public class Value {
	
	private Type type;
	private List<Measurement> measurements;
	private Sensor sensor;
	
	public Value(Node node, Sensor sensor) throws SensorsException {
		this.sensor = sensor;
		
		try {
			int typeId = Integer.parseInt(node.getAttributes().getNamedItem("type").getTextContent());
			type = sensor.getStatus().getType(typeId); 
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
				Measurement measurement = new Measurement(node, this);
				if(measurement.getType().equals("current")) {
					measurements.add(measurement);
				}
			}
		}
	}

	public Type getType() {
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
