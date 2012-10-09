package at.rueckgr.android.ipwe.data;

import java.util.ArrayList;
import java.util.List;

import org.w3c.dom.Node;
import org.w3c.dom.NodeList;

import at.rueckgr.android.ipwe.SensorsApplication;

public class Sensor {
	private int id;
	private String name;
	private List<Value> values;
	private Status status;
	
	public Sensor(Node parentNode, Status status) throws SensorsException {
		this.status = status;
		
		try {
			id = Integer.parseInt(parentNode.getAttributes().getNamedItem("id").getTextContent());
			name = parentNode.getAttributes().getNamedItem("name").getTextContent();
		}
		catch (NumberFormatException e) {
			throw new SensorsException(e);
		}
		catch (NullPointerException e) {
			throw new SensorsException(e);
		}
		
		values = new ArrayList<Value>();
		NodeList nodes = parentNode.getChildNodes();
		boolean nodeProcessed = false;
		for(int a=0; a<nodes.getLength(); a++) {
			Node node = nodes.item(a);
			if(node.getNodeType() == Node.ELEMENT_NODE && node.getNodeName().equals("values")) {
				if(nodeProcessed) {
					throw new SensorsException("Duplicate <values> element inside <sensor> element in XML input from API.");
				}
				nodeProcessed = true;
				processNode(node);
			}
		}
	}

	private void processNode(Node parentNode) throws SensorsException {
		NodeList nodes = parentNode.getChildNodes();
		for(int a=0; a<nodes.getLength(); a++) {
			Node node = nodes.item(a);
			if(node.getNodeType() == Node.ELEMENT_NODE && node.getNodeName().equals("value")) {
				values.add(new Value(node, this));
			}
		}
	}

	public int getId() {
		return id;
	}

	public String getName() {
		return name;
	}

	public List<Value> getValues() {
		return values;
	}
	
	@Override
	public String toString() {
		return "[Sensor:id=" + id + ";name=" + name + ";values=" + values.toString() + "]";
	}

	public Status getStatus() {
		return status;
	}

	public int getStateCount(State state) {
		int count = 0;
		
		for(Value value : values) {
			count += value.getStateCount(state);
		}
		
		return count;
	}

	public List<Measurement> getMeasurements() {
		List<Measurement> measurements = new ArrayList<Measurement>();
		
		for(Value value : values) {
			measurements.addAll(value.getMeasurements());
		}
		
		return measurements;
	}

	public SensorsApplication getApplication() {
		return status.getApplication();
	}
}
