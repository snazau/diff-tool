import numpy as np

class Differ:
	def __init__(self, a, b):
		self.a = a
		self.b = b

	def diff(self):
		diff_history = []
		path = Differ.backtrack(self.a, self.b)
		n, m = len(self.a), len(self.b)

		for (prev_x, prev_y), (x, y) in path:
			# print(prev_x, prev_y, x, y)
			# if prev_x < n and prev_y < m:
			a_line, b_line = self.a[prev_x % n], self.b[prev_y % m]

			if x == prev_x:
				diff_history.append({"type" : "+", "old_line" : "", "new_line" : b_line})
			elif y == prev_y:
				diff_history.append({"type" : "-", "old_line" : a_line, "new_line" : ""})
			else:
				diff_history.append({"type" : "=", "old_line" : a_line, "new_line" : b_line})
	
		return diff_history

	@staticmethod
	def shortest_edit_path(a, b):
		n = len(a)
		m = len(b)
		max_len = n + m
		xs = [0] * (max_len * 2 + 1)
		trace = []

		for depth in range(0, max_len + 1):
			trace.append(xs.copy())
			for k in range(-depth, depth + 1, 2):

				if k == -depth or (k != depth and xs[k - 1] < xs[k + 1]):
					x = xs[k + 1]
				else:
					x = xs[k - 1] + 1
				y = x - k

				while x < n and y < m and a[x] == b[y]:
					x, y = x + 1, y + 1

				xs[k] = x

				if x >= n and y >= m:
					return trace, depth

	@staticmethod
	def backtrack(a, b):
		x, y = len(a), len(b)
		trace, depth = Differ.shortest_edit_path(a, b)

		path = []

		for d, xs in reversed(list(enumerate(trace))):
			# print(d, xs)
			k = x - y

			if k == -d or (k != d and xs[k - 1] < xs[k + 1]):
				prev_k = k + 1
			else:
				prev_k = k - 1

			prev_x = xs[prev_k]
			prev_y = prev_x - prev_k

			while x > prev_x and y > prev_y:
				path.append(((x - 1, y - 1), (x, y)))
				x, y = x - 1, y - 1

			if d > 0:
				path.append(((prev_x, prev_y), (x, y)))

			x, y = prev_x, prev_y

		return path


if __name__ == "__main__":
	trace, depth = Differ.shortest_edit_path("ABCABBA", "CBABAD")
	print(depth, len(trace))
	print(trace)
	# trace, depth = Differ.shortest_edit_path("ABC", "DEF")
	# print(depth)

	# path = Differ.backtrack("ABCABBA", "CBABAC")
	# for move in reversed(path):
	# 	print(move[0], "->", move[1])

	# d1 = Differ("ABCABBA\nABBF", "CBABAC\nABBA")
	d1 = Differ("ABCABBA", "CBABAD")
	# d1 = Differ(["while x > prev_x and y > prev_y:",
	# 				"path.append(((x - 1, y - 1), (x, y)))",
	# 				"x, y = x + 1, y + 1",
	
	# 				"if d > 0:",
	# 					"path.append(((prev_x, prev_y), (x, y)))",
	
	# 				"x, y = prev_x, prev_y)"],
	# 			["while x > prev_x and y > prev_y:",
	# 				"path.append(((x - 1, y - 1), (x, y)))",
	# 				"x, y = x - 1, y - 1",
	
	# 				"if d > 0:",
	# 					"path.append(((prev_x, prev_y), (x, y)))",
	
	# 				"x, y = prev_x, prev_y)"])
	diff_history = d1.diff()

	for action in reversed(diff_history):
		print(action["type"], action["old_line"], action["new_line"])