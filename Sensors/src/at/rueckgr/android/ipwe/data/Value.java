package at.rueckgr.android.ipwe.data;

import java.util.ArrayList;
import java.util.List;

import org.w3c.dom.Node;
import org.w3c.dom.NodeList;

public class Value {
	
	// TODO replace by enum/class
	private int type;
	private List<Measurement> measurements;
	
	public Value(Node node) {
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
				measurements.add(new Measurement(node));
			}
		}
	}

	public int getType() {
		return type;
	}
	
	public String toString() {
		return "[Value:type=" + type + ";measurements=" + measurements.toString() + "]";
	}
}
