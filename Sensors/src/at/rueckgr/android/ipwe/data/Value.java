package at.rueckgr.android.ipwe.data;

import java.util.ArrayList;
import java.util.List;

import org.w3c.dom.Node;
import org.w3c.dom.NodeList;

public class Value {
	
	// TODO replace by enum/class
	private int type;
	private List<Measurement> measurements;
	private Sensor sensor;
	
	public Value(Node node, Sensor sensor) {
		this.sensor = sensor;
		// TODO possible NumberFormatException
		// TODO possibly null
		type = Integer.parseInt(node.getAttributes().getNamedItem("type").getTextContent());

		processNode(node);
	}

	private void processNode(Node parentNode) {
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
}
