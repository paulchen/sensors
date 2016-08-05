package at.rueckgr.android.ipwe.data;

import java.io.IOException;
import java.io.InputStream;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.parsers.ParserConfigurationException;

import org.w3c.dom.Document;
import org.w3c.dom.NamedNodeMap;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;
import org.xml.sax.SAXException;

import android.util.SparseArray;
import at.rueckgr.android.ipwe.SensorsApplication;

public class Status {
	private List<Sensor> sensors;
	private SensorsApplication application;
	private SparseArray<Type> types;

	public Status(SensorsApplication application) {
		this.application = application;
		
		types = new SparseArray<Type>();
	}
	
	public void update() throws SensorsException {
		
		String url = application.getSettingsURL() + "?action=status";
		
		InputStream inputStream = application.executeHttpGet(url);
		DocumentBuilderFactory documentBuilderFactory = DocumentBuilderFactory.newInstance();
		DocumentBuilder documentBuilder;
		try {
			documentBuilder = documentBuilderFactory.newDocumentBuilder();
		}
		catch (ParserConfigurationException e) {
			throw new SensorsException(e);
		}
		try {
			Document document = documentBuilder.parse(inputStream);
			NodeList nodes = document.getChildNodes();
			
			sensors = new ArrayList<Sensor>();
			boolean nodeProcessed = false;
			for(int a=0; a<nodes.getLength(); a++) {
				Node node = nodes.item(a);
				if(node.getNodeType() == Node.ELEMENT_NODE && node.getNodeName().equals("sensors")) {
					if(nodeProcessed) {
						throw new SensorsException("Duplicate <sensors> as root element in XML input from API.");
					}
					nodeProcessed = true;
					processNode(node);
				}
			}
			
			// TODO more checks for validity?
			if(!nodeProcessed) {
				throw new SensorsException("Invalid reply from server.");
			}
		}
		catch (SAXException e) {
			throw new SensorsException(e);
		}
		catch (IOException e) {
			throw new SensorsException(e);
		}
	}

	private void processNode(Node parentNode) throws SensorsException {
		NodeList nodes = parentNode.getChildNodes();
		for(int a=0; a<nodes.getLength(); a++) {
			Node node = nodes.item(a);
			if(node.getNodeType() == Node.ELEMENT_NODE && node.getNodeName().equals("sensor")) {
				sensors.add(new Sensor(node, this));
			}
			else if(node.getNodeType() == Node.ELEMENT_NODE && node.getNodeName().equals("types")) {
				processTypes(node);
			}
			else if(node.getNodeType() == Node.ELEMENT_NODE && node.getNodeName().equals("states")) {
				application.initStates(node);
			}
		}
	}

	private void processTypes(Node parentNode) throws SensorsException {
		NodeList nodes = parentNode.getChildNodes();
		for(int a=0; a<nodes.getLength(); a++) {
			Node node = nodes.item(a);
			if(node.getNodeName().equals("type")) {
				NamedNodeMap attributes = node.getAttributes();
				int id, decimals;
				String name, format;
				try {
					id = Integer.parseInt(attributes.getNamedItem("id").getTextContent());
					name = attributes.getNamedItem("name").getTextContent();
					format = attributes.getNamedItem("format").getTextContent();
					decimals = Integer.parseInt(attributes.getNamedItem("decimals").getTextContent());
				}
				catch (NumberFormatException e) {
					throw new SensorsException(e);
				}
				catch (NullPointerException e) {
					throw new SensorsException(e);
				}
				
				Integer min, max;
				try {
					min = Integer.parseInt(attributes.getNamedItem("min").getTextContent());
				}
				catch (NullPointerException e) {
					min = null;
				}
				catch (NumberFormatException e) {
					throw new SensorsException(e);
				}
				
				
				try {
					max = Integer.parseInt(attributes.getNamedItem("max").getTextContent());
				}
				catch (NullPointerException e) {
					max = null;
				}
				catch (NumberFormatException e) {
					throw new SensorsException(e);
				}
				
				Type type = new Type(id, name, format, min, max, decimals);
				types.put(id, type);
			}
		}
	}
	public List<Sensor> getSensors() {
		return sensors;
	}
	
	@Override
	public String toString() {
		return "[Status:sensors=" + sensors.toString() + "]";
	}

	public Integer getStateCount(State state) {
		int count = 0;
		
		for(Sensor sensor : sensors) {
			count += sensor.getStateCount(state);
		}
		
		return count;
	}

	public List<Measurement> getMeasurements() {
		List<Measurement> measurements = new ArrayList<Measurement>();
		
		for(Sensor sensor : sensors) {
			measurements.addAll(sensor.getMeasurements());
		}
		
		return measurements;
	}

	public Map<String, Integer> getStateCounts() {
		Map<String, State> states = application.getStates();
		Map<String, Integer> stateCounts = new HashMap<String, Integer>();
		for(String stateName : states.keySet()) {
			stateCounts.put(stateName, getStateCount(states.get(stateName)));
		}
		return stateCounts;
	}

	public SensorsApplication getApplication() {
		return application;
	}
	
	public Type getType(int id) {
		return types.get(id);
	}
}
