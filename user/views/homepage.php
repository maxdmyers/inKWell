			<div class="featurette group">
				<div>
					<section>
						<h3>
							Installation
						</h3>
						<pre><code>
cd $DOCUMENT_ROOT
git clone --recursive git://github.com/dotink/inKWell.git ./
						</code></pre>
					</section>
					<section>
						<h3>A Quick Introduction</h3>
						<p>
							inKWell is a battle-tested beta framework built on <a href="http://www.github.com/wbond">wbond</a>'s <a href="http://www.github.com/wbond/flourish">Flourish</a> library and <a href="http://www.github.com/jeffturcotte">jeffturcotte</a>'s <a href="http://www.github.com/jeffturcotte/moor">Moor</a> router.  It began in mid-2010 as a solution for the rapid development, easy maintenance, and modular extensibility required for freelance positions.
						</p>
						<p>
							Over the past two years it has grown into a stable and robust framework which abandons the cargo-cult mentality of existing MVC and HMVC frameworks, favoring configurability and developer accessibility over hyped up design and development patterns.
						</p>
						<p>
							The result is <em>fast</em>, <em>flexible</em>, and most importantly <em><strong>approachable</strong></em>!
						</p>
					</section>
					<div class="brochure group">
						<section>
							<h4>Getting Started</h4>
							<ul>
								<li>
									<a href="/configuration">Configuration</a>
								</li>
								<li>
									<a href="/routing">Routing</a>
								</li>
								<li>
									<a href="/scaffolding">Scaffolding</a>
								</li>
								<li>
									<a href="/issues">Known Issues</a>
								</li>
							</ul>
						</section>
						<section>
							<h4>Documentation</h4>
							<ul>
								<li>
									<a href="/docs/creating_a_controller">Creating a Controller</a>
								</li>
								<li>
									<a href="/docs/http_support">Handling Common HTTP Headers</a>
								</li>
								<li>
									<a href="/docs/displaying_views">Displaying a View</a>
								</li>
								<li>
									<a href="/docs/view_data">Working with View Data</a>
								</li>
								<li>
									<a href="/docs/view_content">Working with View Content</a>
								</li>
								<li>
									<a href="/docs/database_configuration">Configuring a Database</a>
								</li>
								<li>
									<a href="/docs/using_models">Using Models and RecordSets</a>
								</li>
							</ul>
						</section>
						<section>
							<h4>Architecture</h4>
							<ul>
								<li>
									<a href="/architecture/autoloading">Autoloading</a>
								</li>
								<li>
									<a href="/architecture/peer_controllers">Peer Controllers</a>
								</li>
								<li>
									<a href="/architecture/inkwell_interface">The inKWell Interface</a>
								</li>
							</ul>
						</section>
					</div>
					<section>
						<h3>
							Frequently Asked Questions
						</h3>
						<ol>
							<li>
								How can I get my application files out of the document root?
							</li>
							<li>
								Why are my URLs preceded with /index.php?
							</li>
							<li>
								Can I load third-party classes easily?
							</li>
							<li>
								How can I run a cron script in the context of inKWell?
							</li>
							<li>
								Does inKWell support PHP 5.3 Namespaces?
							</li>
						</ol>
					</section>
				</div>
				<div>
					<h3>Samples</h3>

					<figure>
						<pre><code><%= fHTML::encode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . 'assets/config.sample')) %></code></pre>
						<figcaption>
							A configuration for a user model using all standard configuration keys.
						</figcaption>
					</figure>

					<figure>
						<pre><code><%= fHTML::encode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . 'assets/controller.sample')) %></code></pre>
						<figcaption>
							A simple controller with a single list method
						</figcaption>
					</figure>


					<figure>
						<pre><code><%= fHTML::encode(file_get_contents($_SERVER['DOCUMENT_ROOT'] . 'assets/view.sample')) %></code></pre>
						<figcaption>
							A view using the repeat method with a PHP 5.3 anonymous function for an emitter
						</figcaption>
					</figure>



				</div>
			</div>
