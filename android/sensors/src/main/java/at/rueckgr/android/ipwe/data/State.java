package at.rueckgr.android.ipwe.data;

public final class State implements Comparable<State> {
	private final String name;
	private final String color;
	private boolean ok;
	private int pos;
	
	public State(String name, String color, boolean ok, int pos) {
		super();
		this.name = name;
		this.color = color;
		this.ok = ok;
		this.pos = pos;
	}

	public String getName() {
		return name;
	}

	public String getColor() {
		return color;
	}
	
	@Override
	public String toString() {
		return name;
	}

	@Override
	public int hashCode() {
		final int prime = 31;
		int result = 1;
		result = prime * result + ((name == null) ? 0 : name.hashCode());
		return result;
	}

	@Override
	public boolean equals(Object obj) {
		if (this == obj)
			return true;
		if (obj == null)
			return false;
		if (getClass() != obj.getClass())
			return false;
		State other = (State) obj;
		if (name == null) {
			if (other.name != null)
				return false;
		} else if (!name.equals(other.name))
			return false;
		return true;
	}

	public boolean isOk() {
		return ok;
	}

	public String getLetter() {
		return name.substring(0, 1).toUpperCase();
	}

	@Override
	public int compareTo(State another) {
		if(another.pos == pos) {
			return 0;
		}
		if(pos < another.pos) {
			return -1;
		}
		return 1;
	}
}
