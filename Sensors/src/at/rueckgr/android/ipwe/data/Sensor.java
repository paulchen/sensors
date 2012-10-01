package at.rueckgr.android.ipwe.data;

import java.util.ArrayList;
import java.util.List;

import org.w3c.dom.Node;
import org.w3c.dom.NodeList;

public class Sensor {
	private int id;
	private String name;
	private List<Value> values;
	
	public Sensor(Node parentNode) {
		// TODO possible NumberFormatException
		// TODO possibly null
		id = Integer.parseInt(parentNode.getAttributes().getNamedItem("id").getTextContent());
		// TODO possibly null
		name = parentNode.getAttributes().getNamedItem("name").getTextContent();
		
		values = new ArrayList<Value>();
		NodeList nodes = parentNode.getChildNodes();
		boolean nodeProcessed = false;
		for(int a=0; a<nodes.getLength(); a++) {
			Node node = nodes.item(a);
			if(node.getNodeType() == Node.ELEMENT_NODE && node.getNodeName().equals("values")) {
				if(nodeProcessed) {
					// TODO duplicate <values> element
				}
				nodeProcessed = true;
				processNode(node);
			}
		}
	}

	private void processNode(Node parentNode) {
		NodeList nodes = parentNode.getChildNodes();
		for(int a=0; a<nodes.getLength(); a++) {
			Node node = nodes.item(a);
			if(node.getNodeType() == Node.ELEMENT_NODE && node.getNodeName().equals("value")) {
				values.add(new Value(node));
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
}
